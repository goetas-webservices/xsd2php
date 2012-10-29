<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
				xmlns:env="goetas:envelope"
				xmlns:exslt="http://exslt.org/common"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
				xmlns:xs2php="http://www.mercuriosistemi.com/mercurio/php/schema2php"
				xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<xsl:output omit-xml-declaration="yes" method="xml"/>


	<xsl:template match="/">
		<all>
			<xsl:apply-templates select="env:env//xsd:schema/*" mode="class"/>
		</all>
	</xsl:template>

	<xsl:template match="xsd:attribute" mode="class">
		<taint>
			<xsl:apply-templates select="." />
		</taint>
	</xsl:template>
	<xsl:template match="xsd:attributeGroup|xsd:group" mode="class">
		<taint>
			<xsl:attribute name="name">
				<xsl:value-of select="@name"/>
			</xsl:attribute>
			<xsl:attribute name="ns">
				<xsl:value-of select="ancestor::xsd:schema/@targetNamespace"/>
			</xsl:attribute>
			<xsl:apply-templates />
		</taint>
	</xsl:template>
	<xsl:template  match="xsd:complexType|xsd:simpleType|xsd:element" mode="class">
		<class>
			<xsl:attribute name="name">
				<xsl:value-of select="@name"/>
			</xsl:attribute>
			<xsl:attribute name="ns">
				<xsl:value-of select="ancestor::xsd:schema/@targetNamespace"/>
			</xsl:attribute>
			<xsl:attribute name="complexity">
				<xsl:if test="xsd:simpleContent">
					<xsl:text>simpleType</xsl:text>
				</xsl:if>
				<xsl:if test="not(xsd:simpleContent)">
					<xsl:value-of select="local-name()"/>
				</xsl:if>

			</xsl:attribute>
			<xsl:if test="@abstract='true'">
				<xsl:attribute name="abstract">true</xsl:attribute>
			</xsl:if>

			<xsl:if test="@type and local-name()='element'">
				<extension>
		            <xsl:attribute name="name">
		                <xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', . , string(@type),'name')"/>
		            </xsl:attribute>
		            <xsl:attribute name="ns">
		                <xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', . , string(@type),'ns')"/>
		            </xsl:attribute>
		        </extension>
			</xsl:if>

			<xsl:apply-templates />
		</class>
	</xsl:template>

	<xsl:template match="xsd:complexContent|xsd:simpleContent" >
		<xsl:apply-templates />
	</xsl:template>
	<xsl:template match="xsd:sequence|xsd:choose" >
		<xsl:apply-templates />
	</xsl:template>


	<xsl:template match="xsd:simpleType/xsd:restriction/xsd:enumeration">
		<const value="{@value}"/>
	</xsl:template>

	<xsl:template match="xsd:extension|xsd:restriction" >
		<extension>
			<xsl:attribute name="name">
				<xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', . , string(@base),'name')"/>
			</xsl:attribute>
			<xsl:attribute name="ns">
				<xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', . , string(@base),'ns')"/>
			</xsl:attribute>
		</extension>
		<xsl:apply-templates />
	</xsl:template>

	<xsl:template match="xsd:attribute[@ref]|xsd:attributeGroup[@ref]|xsd:group[@ref]">
		<use>
			<xsl:attribute name="name">
				<xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', . , string(@ref),'name')"/>
			</xsl:attribute>
			<xsl:attribute name="ns">
				<xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', . , string(@ref),'ns')"/>
			</xsl:attribute>
			<xsl:apply-templates/>
		</use>
	</xsl:template>



	<xsl:template match="xsd:element[@use and (@maxOccurs!='0' or not(@maxOccurs))]" >
		<prop>
			<xsl:if test="@maxOccurs='unbounded' or number(@maxOccurs)>1"  >
				<xsl:attribute name="array">true</xsl:attribute>
			</xsl:if>
			<xsl:if test="@nillable='true'">
				<xsl:attribute name="nillable">true</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates/>
		</prop>
	</xsl:template>

	<xsl:template match="xsd:element/xsd:simpleType|xsd:element/xsd:complexType|xsd:attribute/xsd:simpleType" >
		<class>
			<xsl:attribute name="sub-ns">

				<xsl:for-each select="
	                ../ancestor::xsd:element[@name]|
	                ../ancestor::xsd:complexType[@name]|
	                ../ancestor::xsd:simpleType[@name]">
				    <xsl:value-of select="@name"/><xsl:text>#</xsl:text>
				</xsl:for-each>


			</xsl:attribute>
			<xsl:attribute name="name">
				<xsl:value-of select="../@name"/>
			</xsl:attribute>

			<xsl:attribute name="ns">
				<xsl:value-of select="ancestor::xsd:schema/@targetNamespace"/>
			</xsl:attribute>

			<xsl:apply-templates />
		</class>
	</xsl:template>
	<xsl:template match="xsd:element[@name and (@maxOccurs!='0' or not(@maxOccurs))]|xsd:attribute[@name and (@use !='prohibited' or not(@use))]" >
		<prop>
			<xsl:attribute name="name">
				<xsl:value-of select="@name"/>
			</xsl:attribute>
			<xsl:if test="@type">
				<xsl:attribute name="type-name">
					<xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', . , string(@type),'name')"/>
				</xsl:attribute>
				<xsl:attribute name="type-ns">
					<xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', . , string(@type),'ns')"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@maxOccurs='unbounded' or number(@maxOccurs)>1"  >
				<xsl:attribute name="array">true</xsl:attribute>
			</xsl:if>
			<xsl:if test="not(@minOccurs) or number(@minOccurs)>0">
				<xsl:attribute name="required">true</xsl:attribute>
			</xsl:if>
			<xsl:if test="@default">
				<xsl:attribute name="default"><xsl:value-of select="@default"/></xsl:attribute>
			</xsl:if>
			<xsl:if test="@nillable='true'">
				<xsl:attribute name="nillable">true</xsl:attribute>
			</xsl:if>

			<xsl:apply-templates/>
		</prop>
	</xsl:template>



	<xsl:template match="xsd:annotation">
		<doc><xsl:value-of select="." /></doc>
	</xsl:template>

	<xsl:template match="*|text()|@*|comment()|processing-instruction()" mode="clone">
		<xsl:copy>
			<xsl:apply-templates select="@*" mode="clone"/>
			<xsl:apply-templates mode="clone"/>
		</xsl:copy>
	</xsl:template>


</xsl:stylesheet>
